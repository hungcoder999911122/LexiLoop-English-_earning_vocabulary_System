package com.example.englishvocab.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

@Getter
@Setter
@Entity
@Table(name = "Quizzes")
public class Quiz {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer quizID;

    @ManyToOne(optional = false)
    @JoinColumn(name = "topicID")
    private Topic topic;

    @ManyToOne(optional = false)
    @JoinColumn(name = "created_by")
    private User createdBy;

    @Column(nullable = false, length = 150)
    private String title;

    @Column(name = "num_questions")
    private Integer numQuestions;
}
