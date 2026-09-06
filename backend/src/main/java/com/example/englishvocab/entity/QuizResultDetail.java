package com.example.englishvocab.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

@Getter
@Setter
@Entity
@Table(name = "Quiz_Result_Details")
public class QuizResultDetail {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer detailID;

    @ManyToOne(optional = false)
    @JoinColumn(name = "resultID")
    private QuizResult result;

    @ManyToOne(optional = false)
    @JoinColumn(name = "questionID")
    private Question question;

    @ManyToOne
    @JoinColumn(name = "selected_answerID")
    private Answer selectedAnswer;

    @Column(name = "is_correct")
    private Boolean correct;
}
