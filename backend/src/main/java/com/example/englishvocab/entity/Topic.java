package com.example.englishvocab.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

@Getter
@Setter
@Entity
@Table(name = "Topics")
public class Topic {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer topicID;

    @Column(name = "topicName", nullable = false, unique = true, length = 100)
    private String topicName;

    @Column(name = "topicDescription", columnDefinition = "TEXT")
    private String topicDescription;

    @Column(name = "cefr_level", nullable = false)
    private String cefrLevel = "A1";

    @Column(name = "is_public")
    private Boolean isPublic = true;

    @ManyToOne(optional = false, fetch = FetchType.LAZY)
    @JoinColumn(name = "created_by")
    private User createdBy;
}
